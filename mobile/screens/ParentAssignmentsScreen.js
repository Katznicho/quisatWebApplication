import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { createAssignmentsApi } from '../api/assignments';

// Parent assignments screen:
// - shows active assignments
// - allows parent hide (delete from own view)
// - lists hidden assignments
// - allows restore
export function ParentAssignmentsScreen({ baseUrl, token }) {
  const api = useMemo(() => createAssignmentsApi({ baseUrl, token }), [baseUrl, token]);

  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState(null);
  const [assignments, setAssignments] = useState([]);
  const [hiddenAssignments, setHiddenAssignments] = useState([]);
  const [error, setError] = useState(null);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [visible, hidden] = await Promise.all([
        api.listAssignments({ per_page: 25 }),
        api.listHiddenAssignments({ per_page: 25 }),
      ]);
      setAssignments(visible.assignments || []);
      setHiddenAssignments(hidden.assignments || []);
    } catch (e) {
      console.warn('Failed to load assignments', e);
      setError('Failed to load assignments.');
    } finally {
      setLoading(false);
    }
  }, [api]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  async function handleHide(item) {
    setBusyId(item.uuid || item.id);
    try {
      await api.hideAssignment(item.uuid || item.id);
      await loadData();
    } catch (e) {
      console.warn('Failed to hide assignment', e);
      Alert.alert('Error', 'Could not remove assignment from your view.');
    } finally {
      setBusyId(null);
    }
  }

  async function handleRestore(item) {
    setBusyId(item.uuid || item.id);
    try {
      await api.restoreAssignment(item.uuid || item.id);
      await loadData();
    } catch (e) {
      console.warn('Failed to restore assignment', e);
      Alert.alert('Error', 'Could not restore assignment.');
    } finally {
      setBusyId(null);
    }
  }

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Assignments</Text>
      {error ? <Text style={styles.error}>{error}</Text> : null}

      <Text style={styles.sectionTitle}>Active</Text>
      {assignments.length === 0 ? (
        <Text style={styles.empty}>No active assignments.</Text>
      ) : (
        assignments.map((item) => {
          const id = item.uuid || item.id;
          const isBusy = busyId === id;
          return (
            <View key={id} style={styles.card}>
              <Text style={styles.cardTitle}>{item.title || 'Untitled'}</Text>
              <Text style={styles.meta}>
                {item.class_room?.name || item.class_room || 'Class N/A'}
              </Text>
              <Text style={styles.meta}>
                Due: {item.due_date || 'No due date'}
              </Text>
              <Pressable
                style={[styles.button, styles.hideButton, isBusy && styles.buttonDisabled]}
                disabled={isBusy}
                onPress={() => handleHide(item)}
              >
                <Text style={styles.buttonText}>
                  {isBusy ? 'Please wait...' : 'Remove from my view'}
                </Text>
              </Pressable>
            </View>
          );
        })
      )}

      <Text style={[styles.sectionTitle, { marginTop: 16 }]}>Hidden</Text>
      {hiddenAssignments.length === 0 ? (
        <Text style={styles.empty}>No hidden assignments.</Text>
      ) : (
        hiddenAssignments.map((item) => {
          const id = item.uuid || item.id;
          const isBusy = busyId === id;
          return (
            <View key={id} style={styles.card}>
              <Text style={styles.cardTitle}>{item.title || 'Untitled'}</Text>
              <Text style={styles.meta}>
                {item.class_room?.name || item.class_room || 'Class N/A'}
              </Text>
              <Pressable
                style={[styles.button, styles.restoreButton, isBusy && styles.buttonDisabled]}
                disabled={isBusy}
                onPress={() => handleRestore(item)}
              >
                <Text style={styles.buttonText}>
                  {isBusy ? 'Please wait...' : 'Restore'}
                </Text>
              </Pressable>
            </View>
          );
        })
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FB',
  },
  content: {
    paddingHorizontal: 16,
    paddingVertical: 20,
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: '#111827',
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#111827',
    marginBottom: 8,
  },
  error: {
    color: '#B91C1C',
    marginBottom: 10,
  },
  empty: {
    color: '#6B7280',
    marginBottom: 8,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: '600',
    color: '#111827',
  },
  meta: {
    marginTop: 3,
    fontSize: 13,
    color: '#4B5563',
  },
  button: {
    marginTop: 10,
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  hideButton: {
    backgroundColor: '#B91C1C',
  },
  restoreButton: {
    backgroundColor: '#065F46',
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: '#FFFFFF',
    fontWeight: '600',
    fontSize: 13,
  },
});

