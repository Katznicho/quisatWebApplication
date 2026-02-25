import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import { createStudentCharacterApi } from '../api/studentCharacter';

// Staff view: record a character report for a specific student
// and view their recent history.
//
// Expected props:
// - student: { id, full_name, class_room? }
// - baseUrl: API base, e.g. https://your-domain.com/api/v1
// - token: auth token for the staff user

export function StaffCharacterReportScreen({ student, baseUrl, token }) {
  const api = createStudentCharacterApi({ baseUrl, token });

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [reports, setReports] = useState([]);

  const [status, setStatus] = useState('');
  const [headline, setHeadline] = useState('');
  const [notes, setNotes] = useState('');

  // Simple default traits – adjust these keys/labels to match your CZH items
  const [traits, setTraits] = useState([
    { key: 'respect', label: 'Respect', rating: '', comment: '' },
    { key: 'responsibility', label: 'Responsibility', rating: '', comment: '' },
    { key: 'teamwork', label: 'Teamwork', rating: '', comment: '' },
  ]);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const data = await api.listReports(student.id);
        if (!mounted) return;
        setReports(data);
        // Pre-fill from the latest report if available
        if (data.length > 0) {
          const latest = data[0];
          setStatus(latest.status || '');
          setHeadline(latest.headline || '');
          setNotes(latest.notes || '');
          if (Array.isArray(latest.traits) && latest.traits.length > 0) {
            setTraits((prev) =>
              prev.map((t) => {
                const found = latest.traits.find((lt) => lt.key === t.key);
                return found ? { ...t, ...found } : t;
              }),
            );
          }
        }
      } catch (e) {
        console.warn('Failed to load character reports', e);
      } finally {
        if (mounted) setLoading(false);
      }
    })();
    return () => {
      mounted = false;
    };
  }, [student.id]);

  async function handleSave() {
    try {
      setSaving(true);
      const today = new Date().toISOString().slice(0, 10);
      const cleanTraits = traits.map((t) => ({
        key: t.key,
        label: t.label,
        rating: t.rating || null,
        comment: t.comment || null,
      }));

      const saved = await api.saveReport({
        studentId: student.id,
        recordDate: today,
        status: status || null,
        headline: headline || null,
        notes: notes || null,
        traits: cleanTraits,
      });

      // Refresh list with new / updated report at the top
      setReports((prev) => {
        const filtered = prev.filter((r) => r.id !== saved.id);
        return [saved, ...filtered];
      });
    } catch (e) {
      console.warn('Failed to save character report', e);
    } finally {
      setSaving(false);
    }
  }

  function updateTrait(key, updates) {
    setTraits((prev) =>
      prev.map((t) => (t.key === key ? { ...t, ...updates } : t)),
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <Text style={styles.title}>{student.full_name}</Text>
        {student.class_room?.name ? (
          <Text style={styles.subtitle}>{student.class_room.name}</Text>
        ) : null}

        <Text style={styles.sectionTitle}>Today&apos;s Character Report</Text>

        <TextInput
          value={status}
          onChangeText={setStatus}
          placeholder="Overall Status (e.g. On Track, At Risk)"
          style={styles.input}
        />

        <TextInput
          value={headline}
          onChangeText={setHeadline}
          placeholder="Headline (short summary for parents)"
          style={styles.input}
        />

        <TextInput
          value={notes}
          onChangeText={setNotes}
          placeholder="Detailed notes (only visible to this child's parents)"
          multiline
          style={[styles.input, styles.textArea]}
        />

        <Text style={styles.sectionTitle}>Traits</Text>
        {traits.map((t) => (
          <View key={t.key} style={styles.traitCard}>
            <Text style={styles.traitLabel}>{t.label}</Text>
            <TextInput
              value={t.rating}
              onChangeText={(text) => updateTrait(t.key, { rating: text })}
              placeholder="Rating (e.g. Excellent, Good, Needs Support)"
              style={styles.input}
            />
            <TextInput
              value={t.comment}
              onChangeText={(text) => updateTrait(t.key, { comment: text })}
              placeholder="Comment (optional)"
              multiline
              style={[styles.input, styles.textAreaSmall]}
            />
          </View>
        ))}

        <TouchableOpacity
          style={[styles.button, saving && styles.buttonDisabled]}
          onPress={handleSave}
          disabled={saving}
        >
          {saving ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.buttonText}>Save Character Report</Text>
          )}
        </TouchableOpacity>

        <Text style={styles.sectionTitle}>Recent Reports</Text>
        {loading ? (
          <ActivityIndicator />
        ) : reports.length === 0 ? (
          <Text style={styles.emptyText}>No reports yet for this student.</Text>
        ) : (
          reports.map((r) => (
            <View key={r.id} style={styles.reportCard}>
              <Text style={styles.reportDate}>{r.record_date}</Text>
              <Text style={styles.reportStatus}>
                {r.status || 'No status set'}
              </Text>
              {r.headline ? (
                <Text style={styles.reportHeadline}>{r.headline}</Text>
              ) : null}
              {r.notes ? <Text style={styles.reportNotes}>{r.notes}</Text> : null}
            </View>
          ))
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FB',
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 20,
    paddingBottom: 24,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: '#111827',
  },
  subtitle: {
    marginTop: 2,
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 16,
  },
  sectionTitle: {
    marginTop: 16,
    marginBottom: 8,
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
  },
  input: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: '#E5E7EB',
    fontSize: 14,
    marginBottom: 10,
  },
  textArea: {
    minHeight: 90,
    textAlignVertical: 'top',
  },
  textAreaSmall: {
    minHeight: 70,
    textAlignVertical: 'top',
  },
  traitCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  traitLabel: {
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 6,
    color: '#111827',
  },
  button: {
    marginTop: 20,
    backgroundColor: '#011478',
    borderRadius: 999,
    paddingVertical: 12,
    alignItems: 'center',
  },
  buttonDisabled: {
    opacity: 0.7,
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 15,
  },
  emptyText: {
    fontSize: 14,
    color: '#6B7280',
    marginTop: 4,
  },
  reportCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginTop: 8,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  reportDate: {
    fontSize: 12,
    color: '#6B7280',
  },
  reportStatus: {
    fontSize: 14,
    fontWeight: '600',
    marginTop: 4,
  },
  reportHeadline: {
    fontSize: 14,
    color: '#111827',
    marginTop: 2,
  },
  reportNotes: {
    fontSize: 13,
    color: '#4B5563',
    marginTop: 4,
  },
});

